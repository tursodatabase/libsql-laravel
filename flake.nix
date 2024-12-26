{
  description = "A very basic flake";

  inputs = {
    nixpkgs.url = "github:nixos/nixpkgs?ref=nixos-unstable";
    flake-utils = {
      url = "github:numtide/flake-utils";
    };
  };
  outputs = { self, nixpkgs, flake-utils }:
    flake-utils.lib.eachDefaultSystem (system:
      let
        pkgs = import nixpkgs {
          inherit system;
          overlays = [
            (final: prev: {
              php = prev.php83.withExtensions ({ enabled, all }: enabled ++ [ all.ffi ]);
            })
          ];
        };
      in
      {
        devShells.default =
          with pkgs;
          mkShell {
            nativeBuildInputs = [
              rustPlatform.bindgenHook
            ];

            buildInputs = [
              php.packages.composer
              php
              turso-cli
            ] ++ lib.optionals stdenv.isDarwin [ iconv ];
          };
      });
}

